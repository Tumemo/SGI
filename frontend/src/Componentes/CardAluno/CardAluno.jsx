function CardAluno(props) {
    return (
        <div className="w-80 mx-auto my-5 shadow-lg rounded-md p-5 bg-white">
            <div className="flex justify-between items-center mb-4">
                <h2 className="text-xl font-bold">{props.nome}</h2>
                <div className="flex items-center gap-2">
                    <input className="w-5 h-5 accent-gray-700" type="checkbox" name="status" id="status" defaultChecked />
                    <p className="text-lg">Ativo</p>
                </div>
            </div>

            <div className="flex  items-center">

                {props.aberto === 'true' ? (

                    <div className="flex flex-col gap-2 w-full">
                        <div className="flex justify-between w-full">
                            <p className="text-gray-400">({props.modalidade})</p>
                            <div className="flex items-center gap-2">
                                <input type="number" className="w-16 bg-gray-100 p-1 rounded text-center" defaultValue="1" />
                                <span className="text-sm">pontos</span>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            {/* n coloquei a pesquisa, pq achei melhor criar um componente de pesquisa */}
                            <button className="bg-green-600 text-white px-6 py-1 rounded-full text-sm">Enviar</button>
                        </div>
                    </div>
                ) : (
                    <div className="relative flex justify-between w-full">
                        <p className="text-gray-400">({props.modalidade})</p>

                        {/* essa parte pra baixo eu copiei */}
                        {/* ========================================= */}
                        <select className=" bg-transparent pr-8 py-1 text-gray-400 outline-none">
                            <option>Penalizar</option>
                        </select>
                        <img src="/icons/seta-icon.svg" className="absolute right-0 top-1/2 -translate-y-1/2 w-3 rotate-90" alt="" />
                        {/* ========================================= */}
                    </div>
                )}
            </div>
        </div>

    )
}

export default CardAluno