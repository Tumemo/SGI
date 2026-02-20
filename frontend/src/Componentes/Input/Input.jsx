function Input(props){
    return( 
        <div className="my-3">
            <input className="border border-gray-400 p-2 w-[80%] rounded-md" type={props.tipo} placeholder={props.placeholder}/>
        </div>
    )
}

export default Input